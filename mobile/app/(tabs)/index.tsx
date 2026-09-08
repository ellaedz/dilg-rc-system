import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { useRef, useState } from 'react';
import {
  Image,
  ImageBackground,
  type NativeScrollEvent,
  type NativeSyntheticEvent,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { PrimaryButton } from '@/components/PrimaryButton';
import { Screen } from '@/components/Screen';
import { colors } from '@/constants/colors';

const HOME_SLIDES = [
  {
    accessibilityLabel: 'A citizen using a phone to document a road-clearing concern',
    source: require('../../assets/images/home-slide-report.png'),
    tagline: 'Clear roads, safer communities.',
    title: 'Road Clearing',
  },
  {
    accessibilityLabel: 'Construction materials occupying part of a public road',
    source: require('../../assets/images/home-slide-construction-materials.png'),
    tagline: 'Keep building materials off public roads.',
    title: 'Construction Materials',
  },
  {
    accessibilityLabel: 'Garbage and debris obstructing the roadside',
    source: require('../../assets/images/home-slide-garbage-debris.png'),
    tagline: 'Keep streets clean and obstruction-free.',
    title: 'Garbage Debris',
  },
  {
    accessibilityLabel: 'An illegally parked vehicle blocking a sidewalk in Santa Cruz, Laguna',
    source: require('../../assets/images/home-slide-illegal-parking.jpg'),
    tagline: 'Keep roads and sidewalks passable.',
    title: 'Illegal Parking',
  },
  {
    accessibilityLabel: 'Objects obstructing a public road',
    source: require('../../assets/images/home-slide-road-obstruction.png'),
    tagline: 'Keep every lane clear and moving.',
    title: 'Road Obstruction',
  },
  {
    accessibilityLabel: 'A sidewalk obstruction in Santa Cruz, Laguna',
    source: require('../../assets/images/home-slide-sidewalk-obstruction.jpg'),
    tagline: 'Keep walkways safe and accessible.',
    title: 'Sidewalk Obstruction',
  },
];

export default function HomeScreen() {
  const sliderRef = useRef<ScrollView>(null);
  const [activeSlide, setActiveSlide] = useState(0);
  const [sliderWidth, setSliderWidth] = useState(320);

  function handleSlideEnd(event: NativeSyntheticEvent<NativeScrollEvent>) {
    const next = Math.round(event.nativeEvent.contentOffset.x / sliderWidth);
    setActiveSlide(Math.max(0, Math.min(HOME_SLIDES.length - 1, next)));
  }

  function showSlide(index: number) {
    setActiveSlide(index);
    sliderRef.current?.scrollTo({ animated: true, x: index * sliderWidth });
  }

  return (
    <Screen>
      <View style={styles.communityCard}>
        <Image
          accessibilityLabel="CIVICLEAR location and clear-road logo"
          resizeMode="contain"
          source={require('../../assets/images/civiclear-logo.png')}
          style={styles.logo}
        />
        <Text style={styles.title}>Help Keep Our Community Safe</Text>
        <Text style={styles.subtitle}>See it. Report it. Clear the way.</Text>
      </View>

      <View style={styles.actions}>
        <PrimaryButton title="ⓘ  Report Violation" onPress={() => router.push('/submit-report')} />
        <PrimaryButton title="▣  View My Reports" variant="outline" onPress={() => router.push('/report-history')} />
      </View>

      <View
        onLayout={(event) => setSliderWidth(Math.round(event.nativeEvent.layout.width))}
        style={styles.sliderCard}
      >
        <ScrollView
          horizontal
          onMomentumScrollEnd={handleSlideEnd}
          pagingEnabled
          ref={sliderRef}
          showsHorizontalScrollIndicator={false}
        >
          {HOME_SLIDES.map((slide, index) => (
            <Pressable
              accessibilityHint="Shows the next picture"
              accessibilityLabel={slide.accessibilityLabel}
              accessibilityRole="button"
              key={slide.accessibilityLabel}
              onPress={() => showSlide((index + 1) % HOME_SLIDES.length)}
              style={[styles.slide, { width: sliderWidth }]}
            >
              <ImageBackground
                accessibilityLabel={slide.accessibilityLabel}
                imageStyle={styles.slideImage}
                resizeMode="cover"
                source={slide.source}
                style={styles.slideImage}
              >
                {slide.title ? (
                  <LinearGradient
                    colors={[
                      'rgba(8, 47, 107, 0.12)',
                      'rgba(7, 40, 92, 0.62)',
                      'rgba(3, 25, 63, 0.96)',
                    ]}
                    locations={[0, 0.58, 1]}
                    style={styles.slideGradient}
                  >
                    <Text style={styles.slideTitle}>{slide.title}</Text>
                    <Text style={styles.slideTagline}>{slide.tagline}</Text>
                  </LinearGradient>
                ) : null}
              </ImageBackground>
            </Pressable>
          ))}
        </ScrollView>
        <View style={styles.dots}>
          {HOME_SLIDES.map((slide, index) => (
            <Pressable
              accessibilityLabel={`Show picture ${index + 1}`}
              accessibilityRole="button"
              key={slide.accessibilityLabel}
              onPress={() => showSlide(index)}
              style={[styles.dot, index === activeSlide && styles.activeDot]}
            />
          ))}
        </View>
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  communityCard: {
    alignItems: 'center',
    backgroundColor: colors.card,
    borderColor: colors.border,
    borderRadius: 10,
    borderWidth: 1,
    gap: 10,
    marginTop: 4,
    paddingHorizontal: 24,
    paddingVertical: 20,
    shadowColor: '#102A5C',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.08,
    shadowRadius: 5,
    elevation: 2,
  },
  logo: { height: 150, width: 150 },
  title: {
    color: '#000000',
    fontSize: 17,
    fontWeight: '900',
    textAlign: 'center',
  },
  subtitle: {
    color: '#27364F',
    fontSize: 12,
    lineHeight: 17,
    maxWidth: 250,
    textAlign: 'center',
  },
  actions: {
    gap: 11,
  },
  sliderCard: {
    backgroundColor: colors.card,
    borderColor: colors.border,
    borderRadius: 12,
    borderWidth: 1,
    overflow: 'hidden',
    width: '100%',
  },
  slide: { height: 175 },
  slideImage: { borderRadius: 11, height: '100%', width: '100%' },
  slideGradient: {
    flex: 1,
    justifyContent: 'flex-end',
    padding: 16,
  },
  slideTitle: {
    color: colors.card,
    fontSize: 22,
    fontWeight: '900',
  },
  slideTagline: {
    color: '#E5EEF9',
    fontSize: 11,
    lineHeight: 15,
    marginTop: 3,
  },
  dots: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: 7,
    justifyContent: 'center',
    paddingVertical: 10,
  },
  dot: {
    backgroundColor: '#CBD5E1',
    borderRadius: 999,
    height: 7,
    width: 7,
  },
  activeDot: {
    backgroundColor: colors.primaryBlue,
    width: 20,
  },
});
